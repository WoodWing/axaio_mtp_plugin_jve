/**
 * IssueInfo.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class IssueInfo  implements java.io.Serializable {
    private java.lang.String id;

    private java.lang.String name;

    private java.lang.Boolean overrulePublication;

    private com.woodwing.enterprise.interfaces.services.wfl.CategoryInfo[] sections;

    private com.woodwing.enterprise.interfaces.services.wfl.State[] states;

    private com.woodwing.enterprise.interfaces.services.wfl.Edition[] editions;

    private java.lang.String description;

    private java.lang.String subject;

    private com.woodwing.enterprise.interfaces.services.wfl.DateTimeOrEmpty publicationDate;

    private java.lang.Boolean reversedRead;

    public IssueInfo() {
    }

    public IssueInfo(
           java.lang.String id,
           java.lang.String name,
           java.lang.Boolean overrulePublication,
           com.woodwing.enterprise.interfaces.services.wfl.CategoryInfo[] sections,
           com.woodwing.enterprise.interfaces.services.wfl.State[] states,
           com.woodwing.enterprise.interfaces.services.wfl.Edition[] editions,
           java.lang.String description,
           java.lang.String subject,
           com.woodwing.enterprise.interfaces.services.wfl.DateTimeOrEmpty publicationDate,
           java.lang.Boolean reversedRead) {
           this.id = id;
           this.name = name;
           this.overrulePublication = overrulePublication;
           this.sections = sections;
           this.states = states;
           this.editions = editions;
           this.description = description;
           this.subject = subject;
           this.publicationDate = publicationDate;
           this.reversedRead = reversedRead;
    }


    /**
     * Gets the id value for this IssueInfo.
     * 
     * @return id
     */
    public java.lang.String getId() {
        return id;
    }


    /**
     * Sets the id value for this IssueInfo.
     * 
     * @param id
     */
    public void setId(java.lang.String id) {
        this.id = id;
    }


    /**
     * Gets the name value for this IssueInfo.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this IssueInfo.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the overrulePublication value for this IssueInfo.
     * 
     * @return overrulePublication
     */
    public java.lang.Boolean getOverrulePublication() {
        return overrulePublication;
    }


    /**
     * Sets the overrulePublication value for this IssueInfo.
     * 
     * @param overrulePublication
     */
    public void setOverrulePublication(java.lang.Boolean overrulePublication) {
        this.overrulePublication = overrulePublication;
    }


    /**
     * Gets the sections value for this IssueInfo.
     * 
     * @return sections
     */
    public com.woodwing.enterprise.interfaces.services.wfl.CategoryInfo[] getSections() {
        return sections;
    }


    /**
     * Sets the sections value for this IssueInfo.
     * 
     * @param sections
     */
    public void setSections(com.woodwing.enterprise.interfaces.services.wfl.CategoryInfo[] sections) {
        this.sections = sections;
    }


    /**
     * Gets the states value for this IssueInfo.
     * 
     * @return states
     */
    public com.woodwing.enterprise.interfaces.services.wfl.State[] getStates() {
        return states;
    }


    /**
     * Sets the states value for this IssueInfo.
     * 
     * @param states
     */
    public void setStates(com.woodwing.enterprise.interfaces.services.wfl.State[] states) {
        this.states = states;
    }


    /**
     * Gets the editions value for this IssueInfo.
     * 
     * @return editions
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Edition[] getEditions() {
        return editions;
    }


    /**
     * Sets the editions value for this IssueInfo.
     * 
     * @param editions
     */
    public void setEditions(com.woodwing.enterprise.interfaces.services.wfl.Edition[] editions) {
        this.editions = editions;
    }


    /**
     * Gets the description value for this IssueInfo.
     * 
     * @return description
     */
    public java.lang.String getDescription() {
        return description;
    }


    /**
     * Sets the description value for this IssueInfo.
     * 
     * @param description
     */
    public void setDescription(java.lang.String description) {
        this.description = description;
    }


    /**
     * Gets the subject value for this IssueInfo.
     * 
     * @return subject
     */
    public java.lang.String getSubject() {
        return subject;
    }


    /**
     * Sets the subject value for this IssueInfo.
     * 
     * @param subject
     */
    public void setSubject(java.lang.String subject) {
        this.subject = subject;
    }


    /**
     * Gets the publicationDate value for this IssueInfo.
     * 
     * @return publicationDate
     */
    public com.woodwing.enterprise.interfaces.services.wfl.DateTimeOrEmpty getPublicationDate() {
        return publicationDate;
    }


    /**
     * Sets the publicationDate value for this IssueInfo.
     * 
     * @param publicationDate
     */
    public void setPublicationDate(com.woodwing.enterprise.interfaces.services.wfl.DateTimeOrEmpty publicationDate) {
        this.publicationDate = publicationDate;
    }


    /**
     * Gets the reversedRead value for this IssueInfo.
     * 
     * @return reversedRead
     */
    public java.lang.Boolean getReversedRead() {
        return reversedRead;
    }


    /**
     * Sets the reversedRead value for this IssueInfo.
     * 
     * @param reversedRead
     */
    public void setReversedRead(java.lang.Boolean reversedRead) {
        this.reversedRead = reversedRead;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof IssueInfo)) return false;
        IssueInfo other = (IssueInfo) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.id==null && other.getId()==null) || 
             (this.id!=null &&
              this.id.equals(other.getId()))) &&
            ((this.name==null && other.getName()==null) || 
             (this.name!=null &&
              this.name.equals(other.getName()))) &&
            ((this.overrulePublication==null && other.getOverrulePublication()==null) || 
             (this.overrulePublication!=null &&
              this.overrulePublication.equals(other.getOverrulePublication()))) &&
            ((this.sections==null && other.getSections()==null) || 
             (this.sections!=null &&
              java.util.Arrays.equals(this.sections, other.getSections()))) &&
            ((this.states==null && other.getStates()==null) || 
             (this.states!=null &&
              java.util.Arrays.equals(this.states, other.getStates()))) &&
            ((this.editions==null && other.getEditions()==null) || 
             (this.editions!=null &&
              java.util.Arrays.equals(this.editions, other.getEditions()))) &&
            ((this.description==null && other.getDescription()==null) || 
             (this.description!=null &&
              this.description.equals(other.getDescription()))) &&
            ((this.subject==null && other.getSubject()==null) || 
             (this.subject!=null &&
              this.subject.equals(other.getSubject()))) &&
            ((this.publicationDate==null && other.getPublicationDate()==null) || 
             (this.publicationDate!=null &&
              this.publicationDate.equals(other.getPublicationDate()))) &&
            ((this.reversedRead==null && other.getReversedRead()==null) || 
             (this.reversedRead!=null &&
              this.reversedRead.equals(other.getReversedRead())));
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
        if (getId() != null) {
            _hashCode += getId().hashCode();
        }
        if (getName() != null) {
            _hashCode += getName().hashCode();
        }
        if (getOverrulePublication() != null) {
            _hashCode += getOverrulePublication().hashCode();
        }
        if (getSections() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getSections());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getSections(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getStates() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getStates());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getStates(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getEditions() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getEditions());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getEditions(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getDescription() != null) {
            _hashCode += getDescription().hashCode();
        }
        if (getSubject() != null) {
            _hashCode += getSubject().hashCode();
        }
        if (getPublicationDate() != null) {
            _hashCode += getPublicationDate().hashCode();
        }
        if (getReversedRead() != null) {
            _hashCode += getReversedRead().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(IssueInfo.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "IssueInfo"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("id");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Id"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("name");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Name"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("overrulePublication");
        elemField.setXmlName(new javax.xml.namespace.QName("", "OverrulePublication"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("sections");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Sections"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "CategoryInfo"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "CategoryInfo"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("states");
        elemField.setXmlName(new javax.xml.namespace.QName("", "States"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "State"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "State"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("editions");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Editions"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Edition"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Edition"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("description");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Description"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("subject");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Subject"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publicationDate");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublicationDate"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "dateTimeOrEmpty"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("reversedRead");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ReversedRead"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
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
