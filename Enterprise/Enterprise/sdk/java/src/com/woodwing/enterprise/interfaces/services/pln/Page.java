/**
 * Page.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pln;

public class Page  implements java.io.Serializable {
    private org.apache.axis.types.UnsignedInt pageOrder;

    private java.lang.Double width;

    private java.lang.Double height;

    private com.woodwing.enterprise.interfaces.services.pln.Attachment[] files;

    private com.woodwing.enterprise.interfaces.services.pln.Edition edition;

    private java.lang.String master;

    private org.apache.axis.types.UnsignedInt pageSequence;

    private java.lang.String pageNumber;

    public Page() {
    }

    public Page(
           org.apache.axis.types.UnsignedInt pageOrder,
           java.lang.Double width,
           java.lang.Double height,
           com.woodwing.enterprise.interfaces.services.pln.Attachment[] files,
           com.woodwing.enterprise.interfaces.services.pln.Edition edition,
           java.lang.String master,
           org.apache.axis.types.UnsignedInt pageSequence,
           java.lang.String pageNumber) {
           this.pageOrder = pageOrder;
           this.width = width;
           this.height = height;
           this.files = files;
           this.edition = edition;
           this.master = master;
           this.pageSequence = pageSequence;
           this.pageNumber = pageNumber;
    }


    /**
     * Gets the pageOrder value for this Page.
     * 
     * @return pageOrder
     */
    public org.apache.axis.types.UnsignedInt getPageOrder() {
        return pageOrder;
    }


    /**
     * Sets the pageOrder value for this Page.
     * 
     * @param pageOrder
     */
    public void setPageOrder(org.apache.axis.types.UnsignedInt pageOrder) {
        this.pageOrder = pageOrder;
    }


    /**
     * Gets the width value for this Page.
     * 
     * @return width
     */
    public java.lang.Double getWidth() {
        return width;
    }


    /**
     * Sets the width value for this Page.
     * 
     * @param width
     */
    public void setWidth(java.lang.Double width) {
        this.width = width;
    }


    /**
     * Gets the height value for this Page.
     * 
     * @return height
     */
    public java.lang.Double getHeight() {
        return height;
    }


    /**
     * Sets the height value for this Page.
     * 
     * @param height
     */
    public void setHeight(java.lang.Double height) {
        this.height = height;
    }


    /**
     * Gets the files value for this Page.
     * 
     * @return files
     */
    public com.woodwing.enterprise.interfaces.services.pln.Attachment[] getFiles() {
        return files;
    }


    /**
     * Sets the files value for this Page.
     * 
     * @param files
     */
    public void setFiles(com.woodwing.enterprise.interfaces.services.pln.Attachment[] files) {
        this.files = files;
    }


    /**
     * Gets the edition value for this Page.
     * 
     * @return edition
     */
    public com.woodwing.enterprise.interfaces.services.pln.Edition getEdition() {
        return edition;
    }


    /**
     * Sets the edition value for this Page.
     * 
     * @param edition
     */
    public void setEdition(com.woodwing.enterprise.interfaces.services.pln.Edition edition) {
        this.edition = edition;
    }


    /**
     * Gets the master value for this Page.
     * 
     * @return master
     */
    public java.lang.String getMaster() {
        return master;
    }


    /**
     * Sets the master value for this Page.
     * 
     * @param master
     */
    public void setMaster(java.lang.String master) {
        this.master = master;
    }


    /**
     * Gets the pageSequence value for this Page.
     * 
     * @return pageSequence
     */
    public org.apache.axis.types.UnsignedInt getPageSequence() {
        return pageSequence;
    }


    /**
     * Sets the pageSequence value for this Page.
     * 
     * @param pageSequence
     */
    public void setPageSequence(org.apache.axis.types.UnsignedInt pageSequence) {
        this.pageSequence = pageSequence;
    }


    /**
     * Gets the pageNumber value for this Page.
     * 
     * @return pageNumber
     */
    public java.lang.String getPageNumber() {
        return pageNumber;
    }


    /**
     * Sets the pageNumber value for this Page.
     * 
     * @param pageNumber
     */
    public void setPageNumber(java.lang.String pageNumber) {
        this.pageNumber = pageNumber;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Page)) return false;
        Page other = (Page) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.pageOrder==null && other.getPageOrder()==null) || 
             (this.pageOrder!=null &&
              this.pageOrder.equals(other.getPageOrder()))) &&
            ((this.width==null && other.getWidth()==null) || 
             (this.width!=null &&
              this.width.equals(other.getWidth()))) &&
            ((this.height==null && other.getHeight()==null) || 
             (this.height!=null &&
              this.height.equals(other.getHeight()))) &&
            ((this.files==null && other.getFiles()==null) || 
             (this.files!=null &&
              java.util.Arrays.equals(this.files, other.getFiles()))) &&
            ((this.edition==null && other.getEdition()==null) || 
             (this.edition!=null &&
              this.edition.equals(other.getEdition()))) &&
            ((this.master==null && other.getMaster()==null) || 
             (this.master!=null &&
              this.master.equals(other.getMaster()))) &&
            ((this.pageSequence==null && other.getPageSequence()==null) || 
             (this.pageSequence!=null &&
              this.pageSequence.equals(other.getPageSequence()))) &&
            ((this.pageNumber==null && other.getPageNumber()==null) || 
             (this.pageNumber!=null &&
              this.pageNumber.equals(other.getPageNumber())));
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
        if (getPageOrder() != null) {
            _hashCode += getPageOrder().hashCode();
        }
        if (getWidth() != null) {
            _hashCode += getWidth().hashCode();
        }
        if (getHeight() != null) {
            _hashCode += getHeight().hashCode();
        }
        if (getFiles() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getFiles());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getFiles(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getEdition() != null) {
            _hashCode += getEdition().hashCode();
        }
        if (getMaster() != null) {
            _hashCode += getMaster().hashCode();
        }
        if (getPageSequence() != null) {
            _hashCode += getPageSequence().hashCode();
        }
        if (getPageNumber() != null) {
            _hashCode += getPageNumber().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Page.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Page"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageOrder");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageOrder"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("width");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Width"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("height");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Height"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("files");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Files"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Attachment"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Attachment"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("edition");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Edition"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Edition"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("master");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Master"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageSequence");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageSequence"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageNumber");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageNumber"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
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
