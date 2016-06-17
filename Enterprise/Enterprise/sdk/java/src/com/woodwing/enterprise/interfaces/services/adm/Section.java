/**
 * Section.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class Section  implements java.io.Serializable {
    private java.math.BigInteger id;

    private java.lang.String name;

    private java.lang.String description;

    private org.apache.axis.types.UnsignedInt sortOrder;

    private com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty deadline;

    private org.apache.axis.types.UnsignedInt expectedPages;

    private com.woodwing.enterprise.interfaces.services.adm.IdName[] statuses;

    public Section() {
    }

    public Section(
           java.math.BigInteger id,
           java.lang.String name,
           java.lang.String description,
           org.apache.axis.types.UnsignedInt sortOrder,
           com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty deadline,
           org.apache.axis.types.UnsignedInt expectedPages,
           com.woodwing.enterprise.interfaces.services.adm.IdName[] statuses) {
           this.id = id;
           this.name = name;
           this.description = description;
           this.sortOrder = sortOrder;
           this.deadline = deadline;
           this.expectedPages = expectedPages;
           this.statuses = statuses;
    }


    /**
     * Gets the id value for this Section.
     * 
     * @return id
     */
    public java.math.BigInteger getId() {
        return id;
    }


    /**
     * Sets the id value for this Section.
     * 
     * @param id
     */
    public void setId(java.math.BigInteger id) {
        this.id = id;
    }


    /**
     * Gets the name value for this Section.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this Section.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the description value for this Section.
     * 
     * @return description
     */
    public java.lang.String getDescription() {
        return description;
    }


    /**
     * Sets the description value for this Section.
     * 
     * @param description
     */
    public void setDescription(java.lang.String description) {
        this.description = description;
    }


    /**
     * Gets the sortOrder value for this Section.
     * 
     * @return sortOrder
     */
    public org.apache.axis.types.UnsignedInt getSortOrder() {
        return sortOrder;
    }


    /**
     * Sets the sortOrder value for this Section.
     * 
     * @param sortOrder
     */
    public void setSortOrder(org.apache.axis.types.UnsignedInt sortOrder) {
        this.sortOrder = sortOrder;
    }


    /**
     * Gets the deadline value for this Section.
     * 
     * @return deadline
     */
    public com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty getDeadline() {
        return deadline;
    }


    /**
     * Sets the deadline value for this Section.
     * 
     * @param deadline
     */
    public void setDeadline(com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty deadline) {
        this.deadline = deadline;
    }


    /**
     * Gets the expectedPages value for this Section.
     * 
     * @return expectedPages
     */
    public org.apache.axis.types.UnsignedInt getExpectedPages() {
        return expectedPages;
    }


    /**
     * Sets the expectedPages value for this Section.
     * 
     * @param expectedPages
     */
    public void setExpectedPages(org.apache.axis.types.UnsignedInt expectedPages) {
        this.expectedPages = expectedPages;
    }


    /**
     * Gets the statuses value for this Section.
     * 
     * @return statuses
     */
    public com.woodwing.enterprise.interfaces.services.adm.IdName[] getStatuses() {
        return statuses;
    }


    /**
     * Sets the statuses value for this Section.
     * 
     * @param statuses
     */
    public void setStatuses(com.woodwing.enterprise.interfaces.services.adm.IdName[] statuses) {
        this.statuses = statuses;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Section)) return false;
        Section other = (Section) obj;
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
            ((this.description==null && other.getDescription()==null) || 
             (this.description!=null &&
              this.description.equals(other.getDescription()))) &&
            ((this.sortOrder==null && other.getSortOrder()==null) || 
             (this.sortOrder!=null &&
              this.sortOrder.equals(other.getSortOrder()))) &&
            ((this.deadline==null && other.getDeadline()==null) || 
             (this.deadline!=null &&
              this.deadline.equals(other.getDeadline()))) &&
            ((this.expectedPages==null && other.getExpectedPages()==null) || 
             (this.expectedPages!=null &&
              this.expectedPages.equals(other.getExpectedPages()))) &&
            ((this.statuses==null && other.getStatuses()==null) || 
             (this.statuses!=null &&
              java.util.Arrays.equals(this.statuses, other.getStatuses())));
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
        if (getDescription() != null) {
            _hashCode += getDescription().hashCode();
        }
        if (getSortOrder() != null) {
            _hashCode += getSortOrder().hashCode();
        }
        if (getDeadline() != null) {
            _hashCode += getDeadline().hashCode();
        }
        if (getExpectedPages() != null) {
            _hashCode += getExpectedPages().hashCode();
        }
        if (getStatuses() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getStatuses());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getStatuses(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Section.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Section"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("id");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Id"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("name");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Name"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("description");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Description"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("sortOrder");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SortOrder"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("deadline");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Deadline"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "dateTimeOrEmpty"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("expectedPages");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ExpectedPages"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("statuses");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Statuses"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "IdName"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "IdName"));
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
