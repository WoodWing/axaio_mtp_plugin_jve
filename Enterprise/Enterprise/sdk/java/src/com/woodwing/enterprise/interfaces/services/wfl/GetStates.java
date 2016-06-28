/**
 * GetStates.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class GetStates  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.String ID;

    private com.woodwing.enterprise.interfaces.services.wfl.Publication publication;

    private com.woodwing.enterprise.interfaces.services.wfl.Issue issue;

    private com.woodwing.enterprise.interfaces.services.wfl.Category section;

    private com.woodwing.enterprise.interfaces.services.wfl.ObjectType type;

    public GetStates() {
    }

    public GetStates(
           java.lang.String ticket,
           java.lang.String ID,
           com.woodwing.enterprise.interfaces.services.wfl.Publication publication,
           com.woodwing.enterprise.interfaces.services.wfl.Issue issue,
           com.woodwing.enterprise.interfaces.services.wfl.Category section,
           com.woodwing.enterprise.interfaces.services.wfl.ObjectType type) {
           this.ticket = ticket;
           this.ID = ID;
           this.publication = publication;
           this.issue = issue;
           this.section = section;
           this.type = type;
    }


    /**
     * Gets the ticket value for this GetStates.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this GetStates.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the ID value for this GetStates.
     * 
     * @return ID
     */
    public java.lang.String getID() {
        return ID;
    }


    /**
     * Sets the ID value for this GetStates.
     * 
     * @param ID
     */
    public void setID(java.lang.String ID) {
        this.ID = ID;
    }


    /**
     * Gets the publication value for this GetStates.
     * 
     * @return publication
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Publication getPublication() {
        return publication;
    }


    /**
     * Sets the publication value for this GetStates.
     * 
     * @param publication
     */
    public void setPublication(com.woodwing.enterprise.interfaces.services.wfl.Publication publication) {
        this.publication = publication;
    }


    /**
     * Gets the issue value for this GetStates.
     * 
     * @return issue
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Issue getIssue() {
        return issue;
    }


    /**
     * Sets the issue value for this GetStates.
     * 
     * @param issue
     */
    public void setIssue(com.woodwing.enterprise.interfaces.services.wfl.Issue issue) {
        this.issue = issue;
    }


    /**
     * Gets the section value for this GetStates.
     * 
     * @return section
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Category getSection() {
        return section;
    }


    /**
     * Sets the section value for this GetStates.
     * 
     * @param section
     */
    public void setSection(com.woodwing.enterprise.interfaces.services.wfl.Category section) {
        this.section = section;
    }


    /**
     * Gets the type value for this GetStates.
     * 
     * @return type
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ObjectType getType() {
        return type;
    }


    /**
     * Sets the type value for this GetStates.
     * 
     * @param type
     */
    public void setType(com.woodwing.enterprise.interfaces.services.wfl.ObjectType type) {
        this.type = type;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetStates)) return false;
        GetStates other = (GetStates) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.ticket==null && other.getTicket()==null) || 
             (this.ticket!=null &&
              this.ticket.equals(other.getTicket()))) &&
            ((this.ID==null && other.getID()==null) || 
             (this.ID!=null &&
              this.ID.equals(other.getID()))) &&
            ((this.publication==null && other.getPublication()==null) || 
             (this.publication!=null &&
              this.publication.equals(other.getPublication()))) &&
            ((this.issue==null && other.getIssue()==null) || 
             (this.issue!=null &&
              this.issue.equals(other.getIssue()))) &&
            ((this.section==null && other.getSection()==null) || 
             (this.section!=null &&
              this.section.equals(other.getSection()))) &&
            ((this.type==null && other.getType()==null) || 
             (this.type!=null &&
              this.type.equals(other.getType())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getTicket() != null) {
            _hashCode += getTicket().hashCode();
        }
        if (getID() != null) {
            _hashCode += getID().hashCode();
        }
        if (getPublication() != null) {
            _hashCode += getPublication().hashCode();
        }
        if (getIssue() != null) {
            _hashCode += getIssue().hashCode();
        }
        if (getSection() != null) {
            _hashCode += getSection().hashCode();
        }
        if (getType() != null) {
            _hashCode += getType().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(GetStates.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetStates"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publication");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Publication"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Publication"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("issue");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Issue"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Issue"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("section");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Section"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Category"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("type");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Type"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ObjectType"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
